# NHL PLAY - NHL Stats Tracking Web Application

![NHL PLAY](assets/img/promo-banner.jpg)

NHL PLAY is a comprehensive web application for tracking NHL hockey statistics, scores, player information, and game data. This project provides hockey fans with detailed analytics, real-time game tracking, and historical data in an intuitive, responsive interface. The application is designed with a focus on performance, user experience, and in-depth hockey analytics.

## Features

### Game Tracking & Analysis
- **Live Game Tracking**: Follow ongoing games with real-time score updates, shot counts, penalties, and other key statistics that refresh automatically every 10 seconds
- **Pre-Game Analysis**: View detailed matchup information including head-to-head team statistics, performance trends, and advanced statistical comparisons
- **Player Matchup Highlights**: "Players to Watch" feature showcasing top performers over recent games
- **Game Advantage Calculator**: Proprietary algorithm that calculates pre-game advantage based on team statistics and matchup history
- **Post-Game Summaries**: Comprehensive game recaps with boxscores, three stars selection, and detailed team statistics
- **Game Recaps & Highlights**: Direct access to official NHL game recap videos and condensed game highlights

### Team Analysis Tools
- **Complete Team Rosters**: Interactive team rosters with dynamic filtering by position (forwards, defensemen, goalies) and animation effects
- **Advanced Team Statistics**: In-depth team analytics including power play percentage, penalty kill percentage, face-off win percentage, and specialized metrics
- **Game Logs**: Historical game results with performance tracking for each team, filterable by game type
- **Injuries Tracking**: Up-to-date injury reports for all teams with player status and estimated return dates
- **Schedule Integration**: Upcoming games schedule with Swiper.js integration for smooth browsing experience

### Player Statistics & Analytics
- **Interactive Player Profiles**: Modal-based player cards with comprehensive statistics and biographical information
- **Advanced Analytics**: Sophisticated metrics including SAT%, USAT%, Even Strength Goal Differential, and position-specific statistics
- **Career Stats Visualization**: Toggle between season and career statistics with seamless content switching
- **Radar Charts**: Advanced visualization of player performance across various statistical categories compared to league average and elite benchmarks
- **Player Comparison Tool**: Side-by-side comparison of two players' statistics with visual differentiation
- **Recent Performance Tracking**: Analysis of player performance over recent games and trends

### League-wide Features
- **Dynamic Standings**: League, conference, and divisional standings with detailed team performance metrics and interactive sorting
- **Stat Leaders Dashboard**: Comprehensive leaderboards for various statistical categories with position filtering (skaters, defense, goalies, rookies)
- **Playoffs Bracket Visualization**: Interactive Stanley Cup Playoffs bracket with series details and game-by-game results
- **Draft Center**: NHL draft rankings, picks, prospect information with year-to-year comparison capability
- **Trade Tracker**: Monitoring of recent trade activity across the league with visual indicators for new trades
- **Three Stars of the Week**: Weekly recognition of top NHL performers

### User Experience Features
- **Responsive Design Architecture**: Fully optimized for desktop, tablet, and mobile devices with device-specific layouts
- **Dark/Light Mode**: Automatic theme switching based on system preferences with smooth visual transitions
- **Single-Page Application Behavior**: Dynamic content loading without full page refreshes for a seamless experience
- **Player Search**: Instant player search functionality with predictive suggestions
- **Animation Effects**: Smooth transitions and loading animations throughout the interface
- **Performance Optimization**: Smart content caching to reduce API calls and improve load times
- **Accessibility Features**: Tooltips, semantic HTML, and keyboard navigation support

## Technical Implementation

### Frontend Architecture
- **Modular JavaScript**: ES6+ modules for clean separation of concerns (player-handlers.js, game-handlers.js, etc.)
- **Advanced DOM Manipulation**: Custom event delegation system for efficient event handling
- **Custom AJAX System**: Sophisticated AJAX handler with URL normalization and content processing
- **Data Visualization**: Integration with Chart.js for advanced player statistics radar charts and performance graphs

### Backend Systems
- **PHP Backend**: Well-structured PHP codebase with organized function libraries
- **API Integration**: Comprehensive integration with NHL Stats API and NHL Web API
- **Caching System**: Multi-level caching strategy for API responses to minimize external requests
- **Data Processing**: Advanced data transformation and calculation for statistical analysis

### Performance Optimizations
- **Resource Bundling**: CSS and JavaScript minification and bundling
- **Lazy Loading**: Deferred loading of non-critical components
- **API Request Management**: Rate limiting and intelligent caching of API requests
- **Mobile Optimization**: Device-specific optimizations using MobileDetect library

### Libraries & Dependencies
- **Chart.js**: Advanced data visualization for player statistics
- **Swiper.js**: Touch-enabled slider implementation for schedules and content
- **JSDataTables**: Enhanced table functionality with sorting and filtering
- **Bootstrap Icons**: Comprehensive icon library for improved UI

## Project Organization

The project follows a well-structured organization:

- **/ajax**: AJAX endpoints for dynamic content loading
- **/assets**: Frontend resources (CSS, JavaScript, images)
- **/assets/js/modules**: Modular JavaScript components
- **/includes**: PHP include files and functions
- **/includes/functions**: Specialized PHP function libraries
- **/includes/data**: Data structure definitions and constants
- **/pages**: Static page templates
- **/templates**: Reusable UI components
- **/cache**: API response cache storage

## Project Status

This project is actively maintained and updated throughout the NHL season with the latest data and features.

## Credits

Created by [emils.graphics](https://emils.graphics)