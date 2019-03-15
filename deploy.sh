#!/bin/sh

setup_git() {
  git config --global user.email "travis@travis-ci.org"
  git config --global user.name "Travis CI"
}

commit_website_files() {
  cd $HOME/build/devility11/commit-remove/
  cp -rf $HOME/build/devility11/commit-remove/src/ $HOME/oeaw_commit/
  cd $HOME/oeaw_commit/src
  git init  
  ls -la
  cat OeawFunctions.php
  git add -A
  git commit --message "FIXED COMMIT"  
  git push -f -q https://devility11:${GITHUB_TOKEN}@github.com/devility11/commit-remove.git live > /dev/null 2>&1
}

setup_git
commit_website_files