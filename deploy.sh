#!/bin/bash
set -o errexit

# config
git config --global user.email "norbert.czirjak@oeaw.ac.at"
git config --global user.name "nczirjak-acdh"

# deploy
cd modules/oeaw/
ls -la
git init
git checkout -b gh-pages   
git add -A
git commit -m "Deploy to Github Pages"
git push --force --quiet "https://devility11:$GITHUB_TOKEN@$github.com/devility11/commit-remove.git" gh-pages > /dev/null 2>&1

