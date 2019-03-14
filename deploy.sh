#!/bin/sh

setup_git() {
  git config --global user.email "travis@travis-ci.org"
  git config --global user.name "Travis CI"
}

commit_website_files() {
  cd $TRAVIS_BUILD_DIR/drupal/modules/oeaw/
  git checkout -b gh-pages
  git add .
  git commit --message "Travis build: $TRAVIS_BUILD_NUMBER"
}

upload_files() {
  git remote add origin-pages https://devility:11${GITHUB_TOKEN}@github.com/devility11/commit-remove.git > /dev/null 2>&1
  git push --force --quiet --set-upstream origin-pages gh-pages 
}

setup_git
commit_website_files
upload_files

    