# Laravel Chat REST APIs
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


## Docker Configuration

This project now includes Docker configurations to simplify setup and deployment. Two different configurations are available:

### 1. Nginx with OPcache (Default)

The default configuration, located in the project root, uses Nginx with OPcache for improved performance.

### 2. Setup without Nginx

An alternative configuration without Nginx is available in the `docker/old_setup` directory.

### Usage Instructions

To use either Docker setup:

1. Ensure your `.env` file is properly configured, especially the database-related variables.
2. Make sure to set a value for `DB_PASSWORD`. Do not leave it empty.
3. Run the following command in your terminal:
```
docker-compose up -d
```

This will build and start the Docker containers in detached mode.

Feel free to use either configuration based on your needs. If you encounter any issues or need further assistance, please open an issue in this repository.

## About Laravel Chat REST API

This Chat application is based on Laravel. It provides industry standard code structure and REST API responses. It consists of all the action control with proper authorization. It's ready for group and direct chat feature along with many more functionality.

- Feature Rich User Resource with search functionality in same api.
- Direct and Group Chat.
- Powerful  User Management System with Roles & Permissions.
- File Upload/Download Supported.
- Pusher for real time communication. (Can be changed to your custom websocket preference. e.g., Socket.io)
- Customizable UI using VueJS.
- Built on Laravel Framework, the most popular
- Access Control for each user action.
- Clean code base to learn from.

## API Usage
- [All API Routes](https://github.com/rashadkhan359/Laravel-Chat-REST-API/blob/master/resources/json/api.json)


## Pending Work
- Group Chat functionality under works
