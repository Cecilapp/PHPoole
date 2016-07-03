#!/bin/bash
set -e

SOURCE_BRANCH="master"
REPO="PHPoole/phpoole.github.io"
TARGET_BRANCH="source"
DOCS_DIR="docs"

if [ $TRAVIS_PHP_VERSION != "5.6" -o "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "$SOURCE_BRANCH" ]; then
    echo "Skipping deploy."
    exit 0
fi

echo "Starting to update gh-pages..."

cp -R $DOCS_DIR $HOME/$DOCS_DIR
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${REPO}.git gh-pages > /dev/null
cd gh-pages
cp -Rf $HOME/$DOCS_DIR/* .
git add -f .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER pushed to gh-pages"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
