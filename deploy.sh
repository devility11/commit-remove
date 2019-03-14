#!/bin/bash
set -o errexit

# config
git config --global user.email "norbert.czirjak@oeaw.ac.at"
git config --global user.name "nczirjak-acdh"

# deploy
cd modules/oeaw/
ls -la
git init
git checkout -b live   
git add -A
git commit -m "Deploy to Github Pages"
git push --f --q https://devility11:$GITHUB_TOKEN@$github.com/devility11/commit-remove.git live > /dev/null 2>&1

