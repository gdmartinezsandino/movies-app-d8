# Movies App

## Setup instructions

### Step #1: Docksal environment setup

**This is a one time setup - skip this if you already have a working Docksal environment.**  

Follow [Docksal environment setup instructions](https://docs.docksal.io/getting-started/setup/)

### Step #2: Project setup

1. Clone this repo into your Projects directory

    ```
    git clone https://github.com/gdmartinezsandino/movies-d8-app YOUR_FOLDER
    cd YOUR_FOLDER
    ```

2. Initialize the site

    This will initialize local settings and install the site via drush

    ```
    fin init
    ```

3. Point your browser to

    ```
    http://YOUR_FOLDER.docksal
    ```

When the automated install is complete the command line output will display the admin username and password.

## Usage

    First you have to enable the required modules
    
    ```
    fin bash
    cd docroot
    drush en dc_vocabularies media
    drush en dc_content_entities 
    drush en dc_content_import
    drush en dc_pages
    ```

    After this you have set your credentials of the API, to do it you have to go http://YOUR_FOLDER.docksal/admin/config/services/api-connection-settings and set your credentials of www.themoviedb.org

### Data import

    ```
    drush import:genres
    ```
    ```
    drush import:movies upcoming 15
    ```
    ```
    drush import:movies popular 500
    ```
    ```
    drush import:actor 500
    ```

### Active the theme

    To set the correct theme you have to go to http://YOUR_FOLDER.docksal/admin/appearance
    and turn on theme 'dc'

## Available Routes

    ```
    http://YOUR_FOLDER.docksal/home
    http://YOUR_FOLDER.docksal/movies
    http://YOUR_FOLDER.docksal/actors
    ```
