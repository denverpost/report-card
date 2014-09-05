# Report Card
Publish an online poll, except with letter grades instead of poll options.

Note in regards to terminology: "Cards" are used to describe what would otherwise be an online poll question. "Votes" are what happens when a reader chooses a letter grade on a card. Card data is stored in a Google spreadsheet, vote data is (probably) stored in a database.

# Getting Started
You'll need a server with Python and a (probably) database to record votes.

You'll also want to create a Google spreadsheet to allow people to create new report cards. NOTE: This could be done strictly on the database side instead.

This repo addresses the front-end requirements of building a Report Card publishing system, and certain back-end requirements.

# Requirements
The back-end parts you'll need to build on your own:
- A form handler to process report card votes.

The back-end parts you'll need to supply:
- A database
- A server to store the report card item javascript (optional)

If you're going to use Google Spreadsheets to store the report card information, these are the fields I'm using:

'Title', 'Description', 'slug', 'Date expires', 'Date launches'

Title and slug are the only required fields.
