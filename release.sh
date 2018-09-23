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
tar_name="carnet-nc-$version.tar.xz"

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
  cur=$(pwd)
  cp . ../CarnetNextcloudTmp -R
  cd ../CarnetNextcloudTmp/
  sudo rm .git -R
  sudo rm templates/CarnetElectron/.git -R
  
  # archive creation + signing
  zip -r "$cur""/$zip_name" *
  cd "$cur"
  rm ../CarnetNextcloudTmp -R
  # temporary setup destruction
)

# Creating the release on GitHub, with the created archives
(
  git push origin --tag

  github-release phief/CarnetNextcloud "$version" master "$changelog" "$zip_name"

  #github-release upload --user phief --repo exode --tag "$version" --name "$zip_name" --file "$zip_name"
  git push origin master
  rm "$zip_name";
)
