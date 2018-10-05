#!/bin/bash

if [ -z "$1" ]; then
  echo "Need version as argument"
  exit -1
fi
if [ -z "$GITHUB_TOKEN" ]; then
  echo "Need GITHUB_TOKEN env set."
  exit -1
fi

branch=$(git symbolic-ref --short -q HEAD)

if [ "$branch" != "master" ]; then
  echo "Need to be on develop branch."
  exit -1
fi

version="v$1"

directory_name="carnet-nc-$version"
zip_name="carnet-nc-$version.zip"
tar_name="carnet-nc-$version.tar.gz"

changelog=$(awk -v version="$version" '/## v/ { printit = $2 == version }; printit;' CHANGELOG.md | grep -v "$version" | sed '1{/^$/d}')

printf "Changelog will be:\\n%s\\n" "$changelog"

read -p "Are you sure to release? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  exit 0
fi
git tag -a "$version" -m "$version"

# Creating the archives
(
  mkdir ../tmpcarnet
  cur=$(pwd)
  cp . ../tmpcarnet/carnet -R
  cd ../tmpcarnet/carnet/
  sudo rm .git -R
  sudo rm templates/CarnetElectron/.git -R
  sudo rm .Trash-1000 -R
  cd ..
  # archive creation + signing
  zip -r "$cur""/$zip_name" carnet
  tar zcvf   "$cur""/$tar_name" carnet
  cd "$cur"
  rm ../tmpcarnet -R
  # temporary setup destruction
)

# Creating the release on GitHub, with the created archives
(
  git push origin --tag

  github-release phief/CarnetNextcloud "$version" master "$changelog" "$zip_name"
  github-release phief/CarnetNextcloud "$version" master "$changelog" "$tar_name"

  #github-release upload --user phief --repo exode --tag "$version" --name "$zip_name" --file "$zip_name"
  git push origin master
  rm "$zip_name";
  openssl dgst -sha512 -sign ~/.nextcloud/certificates/carnet.key "$tar_name" | openssl base64
  rm "$tar_name";

)
