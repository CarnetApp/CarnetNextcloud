#!/bin/bash


git branch -d stable
git push origin --delete stable
git checkout master
git checkout -b stable
git push origin stable
git checkout master
