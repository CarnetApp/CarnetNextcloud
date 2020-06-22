#!/bin/bash

version=$(sed -n -e 's/.*<version>\(.*\)<\/version>.*/\1/p' appinfo/info.xml)
git branch -D stable
git push origin --delete stable
git push framagit --delete stable
git checkout master
git checkout -b stable
git push origin stable
git push framagit stable
git checkout -b stable-$version
git push origin stable-$version
git push framagit stable-$version
git checkout master
