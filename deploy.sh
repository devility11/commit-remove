#!/bin/sh

setup_git() {
  git config --global user.email "travis@travis-ci.org"
  git config --global user.name "Travis CI"
}

commit_website_files() {
  cd $HOME/build/devility11/commit-remove/  
  rsync -av --progress $HOME/build/devility11/commit-remove/ $HOME/build/devility11/commit-remove/oeaw_commit --exclude $HOME/build/devility11/commit-remove/drupal/  
  cd $HOME/build/devility11/commit-remove/oeaw_commit/
  git add $HOME/build/devility11/commit-remove/oeaw_commit/* -A
  git commit --message "Travis build: $TRAVIS_BUILD_NUMBER"
  
}

upload_files() {
  
  git push -f -q https://devility11:${GITHUB_TOKEN}@github.com/devility11/commit-remove.git live > /dev/null 2>&1
  
}

setup_git
commit_website_files
upload_files

    