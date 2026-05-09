# My Media Hub

My Media Hub is a personalized media tracking web application built with PHP and MySQL. It allows users to easily track, organize, and manage their favorite Anime, TV Series, and Movies. The app automatically fetches media details (posters, summaries, etc.) using external APIs, providing a seamless user experience.

## Features

- **Add Media Easily:** Search for any Anime, Series, or Movie. The app integrates with external APIs to automatically fetch cover images and summaries.
  - **Anime:** Powered by [Jikan API](https://jikan.moe/) (Unofficial MyAnimeList API).
  - **Series & Movies:** Powered by [TVmaze API](https://www.tvmaze.com/api).
- **Track Progress:** Keep track of the current season and episode you are on for both Anime and Series.
- **History Tracking:** Mark items as "Finished" to move them from your "To-Watch" list to your "Recently Finished" history.
- **Categorized Dashboard:** View your media beautifully organized into Anime, Series, and Movies grids.
- **Delete & Manage:** Easily remove items from your library permanently.

## Tech Stack

- **Backend:** PHP (PDO for Database Interaction)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Vanilla)
- **Deployment:** Vercel (using the `vercel-php` runtime)

## Project Structure

```
├── api/
│   ├── index.php       # Main dashboard, handles displaying lists, history, and adding media
│   └── details.php     # Details page for updating progress, marking as finished, or deleting
├── style.css           # Global stylesheet for the UI
└── vercel.json         # Vercel deployment configuration mapping routes to the api folder
```

## Setup & Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL Database
- Web Server (Apache/Nginx) or PHP Built-in Server

### Local Development

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd watch
   ```

2. **Database Setup:**
   Create a MySQL database and create the following three tables: `table_anime`, `table_series`, and `table_movies`. 
   
   Example schema for the tables:
   ```sql
   CREATE TABLE table_anime (
       id INT AUTO_INCREMENT PRIMARY KEY,
       title VARCHAR(255) NOT NULL,
       poster_url VARCHAR(500),
       summary TEXT,
       total_eps INT DEFAULT 0,
       current_season INT DEFAULT 1,
       current_ep INT DEFAULT 0,
       status ENUM('to-watch', 'watched') DEFAULT 'to-watch'
   );

   CREATE TABLE table_series LIKE table_anime;
   CREATE TABLE table_movies LIKE table_anime;
   -- Movies don't strictly need total_eps, current_season, and current_ep, but sharing the schema simplifies queries.
   ```

3. **Environment Variables:**
   The application uses environment variables for database connection. Set the following variables in your environment, or temporarily replace them in `index.php` and `details.php`:
   - `DB_HOST` (default: 'mysql-jbala.alwaysdata.net')
   - `DB_NAME` (default: 'jbala_watch')
   - `DB_USER` (default: 'jbala')
   - `DB_PASS` (default: 'sql@2006')

4. **Run the application:**
   You can use the built-in PHP server to run the application locally:
   ```bash
   php -S localhost:8000
   ```
   *Note: Since the routes are configured for Vercel, you might need to access `localhost:8000/api/index.php` directly.*

## Deployment

This project is configured to be deployed on **Vercel** using the `vercel-php` runtime.

1. Install Vercel CLI: `npm i -g vercel`
2. Run `vercel` in the root directory.
3. Add your database environment variables in the Vercel project settings.

## APIs Used
- [Jikan REST API](https://docs.api.jikan.moe/)
- [TVmaze API](https://www.tvmaze.com/api)
