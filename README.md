# BookTrackerAPI

A book tracker API coded in PHP Symfony

## About the app

BookTracker API is an app that tracks your progression of your book readings. It can also organize your books with lists of books. The users can communicate via private chat or grouped chat. They can also review books.

## Setup
- download or clone the repository
- run ```composer install```
- create your booktracker database
- run ```php bin/console d:s:u --force```
- load app fixtures
    -  run ```php bin/console d:f:l```
- run ```symfony serve```

You can view the API doc on this route :
http://localhost:8000/api/doc

## Status
BookTracker API is still in progress