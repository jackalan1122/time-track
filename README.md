# Time Tracker Application

A modern, responsive time tracking application built with PHP, MySQL, and vanilla JavaScript. Track your projects and time spent with an intuitive dashboard and real-time analytics.

## Features

✅ **Real-time Timer** - Start/pause/stop timers for each project  
✅ **Persistent Storage** - Timer state preserved across page refreshes  
✅ **Analytics Dashboard** - View time spent by project and daily activity  
✅ **Responsive Design** - Works on desktop and mobile devices  
✅ **Color-coded Projects** - Visual organization with custom colors  
✅ **Input Validation** - Comprehensive server-side validation  
✅ **Error Handling** - User-friendly error messages and logging  
✅ **Database Security** - Prepared statements prevent SQL injection  

## Requirements

- PHP 7.4+
- MySQL 5.7+
- XAMPP or similar local development environment
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Setup Instructions

### 1. Clone/Copy Project Files
```bash
cp -r time-track /path/to/htdocs/
cd /path/to/htdocs/time-track
```

### 2. Configure Environment
```bash
# Copy example environment file
cp .env.example .env

# Edit .env with your database credentials
# nano .env
```

Edit `.env`:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=time_tracker
APP_ENV=development
SESSION_SECRET=your_secret_key_here
```

### 3. Create Database
```bash
# Using MySQL client
mysql -u root -p < DATABASE_SCHEMA.sql

# Or manually run the SQL commands in phpMyAdmin
```

### 4. Start Development Server
```bash
# If using XAMPP, start Apache and MySQL from XAMPP Control Panel

# Or use PHP built-in server
php -S localhost:8000
```

### 5. Access Application
Open your browser and navigate to:
```
http://localhost:8000
```

## Project Structure

```
time-track/
├── index.php              # Main application frontend
├── api.php                # RESTful API endpoints
├── config.php             # Configuration and utilities
├── .env.example           # Environment configuration template
├── .gitignore             # Git ignore rules
├── DATABASE_SCHEMA.sql    # Database initialization script
└── README.md              # This file
```

## API Endpoints

### Projects
- `GET /api.php?action=get_projects` - List all projects
- `POST /api.php?action=add_project` - Create new project
- `POST /api.php?action=delete_project&id=1` - Delete project

### Time Entries
- `GET /api.php?action=get_entries` - List all entries
- `GET /api.php?action=get_entries&project_id=1` - List entries for project
- `POST /api.php?action=add_entry` - Add time entry
- `POST /api.php?action=delete_entry&id=1` - Delete entry

### Dashboard
- `GET /api.php?action=get_dashboard_stats&period=week` - Get analytics (week/month/year)

## Usage Guide

### Adding a Project
1. Click "Tracker" button in navigation
2. Click the "+" button in the Projects panel
3. Enter project name
4. Select a color
5. Click "Add Project"

### Tracking Time
1. Click "Start Timer" on any project
2. Timer will count up in real-time
3. Click "Pause" to save the current time entry
4. Click "Stop" to abandon without saving

### Viewing Analytics
1. Click "Dashboard" button in navigation
2. Select time period (7 Days, 30 Days, 1 Year)
3. View charts and project breakdown

## Security Features

✅ **Input Validation** - All user inputs validated and sanitized  
✅ **SQL Injection Prevention** - Prepared statements with parameter binding  
✅ **Error Handling** - Comprehensive try-catch with proper error messages  
✅ **Environment Variables** - Sensitive data in .env (excluded from git)  
✅ **HTTP Headers** - Proper Content-Type and status codes  
✅ **Data Type Checking** - Filter var for numeric inputs  

## Performance Improvements

✅ **Pagination Ready** - API supports limit/offset parameters  
✅ **Database Indexes** - Optimized queries with proper indexing  
✅ **Module Architecture** - Organized JavaScript with clear separation of concerns  
✅ **Error Recovery** - Graceful handling of API failures  
✅ **localStorage Persistence** - Efficient timer state management  

## Code Quality

✅ **Comments** - Well-documented functions and logic  
✅ **Naming Conventions** - Clear, descriptive variable/function names  
✅ **DRY Principle** - Reusable functions and services  
✅ **Accessibility** - aria-labels for screen readers  
✅ **Responsive UI** - Mobile-first design approach  

## Troubleshooting

### Database Connection Failed
- Verify MySQL is running
- Check credentials in .env
- Ensure database exists: `CREATE DATABASE time_tracker;`

### Timer Not Persisting
- Check browser localStorage is enabled
- Clear localStorage if corrupted: Open DevTools → Application → Clear Storage

### API Errors
- Check browser console for error messages
- Verify API endpoints in Network tab (DevTools)
- Ensure Content-Type header is `application/json`

## Future Enhancements

- User authentication and multi-user support
- Project categories and tags
- Time entry notes and descriptions
- Export data (CSV, PDF)
- Dark mode support
- Mobile app (React Native)
- Team collaboration features
- Recurring time entries

## License

MIT License - Feel free to use and modify for your needs.

## Support

For issues or questions, please check the code comments or create an issue in your repository.
