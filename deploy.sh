#!/bin/bash
set -o errexit

# config
git config --global user.email "norbert.czirjak@oeaw.ac.at"
git config --global user.name "nczirjak-acdh"

# deploy
ls -la
cd modules/oeaw/
git init
git add -A
git commit -m "Deploy to Github Pages"
git push --force --quiet "https://${GITHUB_TOKEN}@$github.com/${GITHUB_REPO}.git" master:gh-pages > /dev/null 2>&1