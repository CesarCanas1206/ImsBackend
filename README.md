## Setup

In the terminal run `php composer.phar install` (pointing to wherever your composer.phar file is - install if you haven't).

Then when the vendor directories are all set up, you can run `php -S localhost:8000` to set up the local server.

The app can then have the endpoint changed temporarily to `http://localhost:8000/` (in useAPI) to point to your local.

## Tables

This is still being worked on but the original plan was to have everything run through the `collection` and `collection_field` tables - meaning we could add/remove collections (tables) and fields as needed.

Recently due to the complexity/speed of loading items, we have added some main tables for fixed items and joining tables. The fixed tables - like asset, booking, calendar, hirer have limited fields but also extend off the collection_field table.

`asset`
`booking`
`calendar`
`collection`
`collection_field`
`collection_history`
`config`
`dataset`
`file`
`form`
`form_question`
`hirer`
`migrations`
`page`
`page_component`
`password_resets`
`permission`
`personal_access_tokens`
`role`
`role_email`
`schema`
`usage`
`user_asset`
`user_email`
`user_permission`
`users`

## Endpoints

The endpoints/routes are in the routes/api.php file.

There are public routes and then the ones requiring authorisation.

This is currently being developed and is a bit messy. We will eventually need to set up some of the routes that are private as public but with different access.

## Future plans

Ideally for consitency between front-end and back-end, we would like to go to a JS server side setup like Remix or NextJs but as our team is more familiar with PHP and the set up there, we are using the Laravel framework - Once this is more in use and the initial project finished, we can look at moving across.

We would also like to use GraphQL to access data so we can be more specific about what is required and reduce the amount of data loaded in each call.
