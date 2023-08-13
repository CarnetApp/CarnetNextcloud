#!/bin/bash
version=v$(sed -n -e 's/.*<version>\(.*\)<\/version>.*/\1/p' appinfo/info.xml)
git checkout stable
if [ -z "$GITHUB_TOKEN" ]; then
  echo "Need GITHUB_TOKEN env set."
  exit -1
fi

branch=$(git symbolic-ref --short -q HEAD)

if [ "$branch" != "stable" ]; then
  echo "Need to be on stable branch."
  exit -1
fi

#version="v$1"

directory_name="carnet-nc-$version"
zip_name="carnet-nc-$version.zip"
tar_name="carnet-nc-$version.tar.gz"
tar_oc_name="carnet-owncloud-$version.tar.gz"

changelog=$(awk -v version="$version" '/## v/ { printit = $2 == version }; printit;' CHANGELOG.md | grep -v "$version" | sed '1{/^$/d}')

printf "Changelog will be:\\n%s\\n" "$changelog"

read -p "Are you sure to release? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  exit 0
fi
git tag -a "$version" -m "$version"
rm carnet-*-v*
# Creating the archives
(
  sudo rm ../tmpcarnet -R
  mkdir ../tmpcarnet
  cur=$(pwd)
  cp . ../tmpcarnet/carnet -R
  cd ../tmpcarnet/carnet/
  echo "Copying CarnetWebClient"
  sudo rm templates/CarnetWebClient
  cp ../../CarnetWebClient/dist templates/CarnetWebClient -R
  echo "Cleaning"
  sudo rm .git -R
  sudo rm templates/CarnetElectron/.git -R
  sudo rm templates/CarnetElectron/node_modules/ -R
  sudo rm templates/CarnetElectron/build/ -R

  sudo rm templates/CarnetElectron/dist/ -R
  sudo rm .Trash-1000 -R
  sudo rm ACRA*
  sudo rm ./vendor/bin/phpunit #avoid issue with nextcloud when extracting an archive with a symlink
  cd templates/CarnetElectron/
  #bash minify.sh
  cd ../../../

  
  # archive creation + signing
  zip -r "$cur""/$zip_name" carnet
  tar zcvf   "$cur""/$tar_name" carnet
  tmp=$(pwd)
  sudo chown www-data "$tmp" -R
  sudo chown www-data "/home/$USER/.owncloud/certificates/" -R

  sudo -u www-data /var/www/html/owncloud/./occ integrity:sign-app \
  --privateKey=/home/$USER/.owncloud/certificates/carnet.key \
  --certificate=/home/$USER/.owncloud/certificates/carnet.crt \
  --path="$tmp"/carnet
  sudo chown $USER "/home/$USER/.owncloud/certificates/" -R
  tar zcvf   "$cur""/$tar_oc_name" carnet
  cd "$cur"

  sudo rm ../tmpcarnet -R
  # temporary setup destruction
)

# Creating the release on GitHub, with the created archives
(
  git push origin --tag

#  github-release phief/CarnetNextcloud "$version" master "$changelog" "$zip_name"
#  github-release phief/CarnetNextcloud "$version" master "$changelog" "$tar_name"
#  github-release phief/CarnetNextcloud "$version" master "$changelog" "$tar_oc_name"

#  github-release upload --user phief --repo exode --tag "$version" --name "$zip_name" --file "$zip_name"
github-release release --user CarnetApp --tag "$version" --repo CarnetNextcloud
github-release upload --user CarnetApp --tag "$version" --name "$zip_name" --file "$zip_name" --repo CarnetNextcloud
github-release upload --user CarnetApp --tag "$version" --name "$tar_name" --file "$tar_name" --repo CarnetNextcloud
github-release upload --user CarnetApp --tag "$version" --name "$tar_oc_name" --file "$tar_oc_name" --repo CarnetNextcloud
  git push origin main
  openssl dgst -sha512 -sign ~/.nextcloud/certificates/carnet.key "$tar_name" | openssl base64
  echo "https://github.com/CarnetApp/CarnetNextcloud/releases/download/$version/carnet-nc-$version.tar.gz"
  git checkout main
)
