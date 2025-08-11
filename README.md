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

### Team Building & Management Tools
- **Interactive Team Builder**: Drag-and-drop team building interface with real-time lineup management and position validation
- **Draft Mode Simulation**: Advanced draft simulation with customizable challenge filters and round-by-round player selection
- **Depth Chart Visualization**: Real-time lineup organization with line combinations and defensive pairings
- **State Persistence**: Automatic saving and restoration of team builds across sessions using localStorage
- **Bulk Team Operations**: Clear all players, export lineups, and team management utilities

### Community & Social Features
- **Reddit Game Threads**: Automatic discovery and display of live game discussion threads from r/hockey
- **Team-specific Reddit Feeds**: Curated community posts and discussions for individual NHL teams
- **Real-time Community Updates**: Live refreshing of community discussions with intersection observer optimization
- **Social Media Integration**: Links to official team social media and community platforms

### User Experience Features
- **Responsive Design Architecture**: Fully optimized for desktop, tablet, and mobile devices with device-specific layouts
- **Dark/Light Mode**: Automatic theme switching based on system preferences with smooth visual transitions
- **Single-Page Application Behavior**: Dynamic content loading without full page refreshes for a seamless experience
- **Player Search**: Instant player search functionality with predictive suggestions
- **Animation Effects**: Smooth transitions and loading animations throughout the interface
- **Performance Optimization**: Smart content caching to reduce API calls and improve load times
- **Accessibility Features**: Tooltips, semantic HTML, and keyboard navigation support
- **Progressive Enhancement**: Features gracefully degrade for older browsers while maintaining core functionality
- **Lazy Loading**: Intersection observer-based loading of features and content as they enter the viewport

## Technical Implementation

### Frontend Architecture
- **Modular ES6+ JavaScript**: Advanced module system with dynamic loading and dependency management through core/module-loader.js
- **App Class Architecture**: Centralized application management with state handling and lifecycle control via global.js
- **Page Detection System**: Intelligent page type detection and automatic module loading based on content requirements
- **Feature Observer Pattern**: Intersection observer-based lazy loading for performance optimization
- **Advanced DOM Manipulation**: Efficient event delegation system with cached DOM elements and batch operations
- **Custom AJAX System**: Sophisticated AJAX handler with URL normalization and content processing
- **Data Visualization**: Integration with Chart.js for advanced player statistics radar charts and performance graphs
- **State Management**: Global application state with localStorage persistence and cross-tab synchronization

### Backend Systems
- **PHP Backend**: Well-structured PHP codebase with organized function libraries
- **API Integration**: Comprehensive integration with NHL Stats API and NHL Web API
- **Caching System**: Multi-level caching strategy for API responses to minimize external requests
- **Data Processing**: Advanced data transformation and calculation for statistical analysis

### Performance Optimizations
- **Dynamic Module Loading**: ES6 modules loaded only when needed based on page context and user interaction
- **Intersection Observer API**: Advanced lazy loading of features and content with viewport-based triggering
- **Debounced Operations**: Performance optimization for frequent operations (search, scroll, resize events)
- **API Request Management**: Intelligent rate limiting, caching, and batch processing of API requests
- **Mobile Optimization**: Device-specific optimizations using MobileDetect library with responsive breakpoints
- **Memory Management**: Proper cleanup of event listeners and DOM references to prevent memory leaks

### Libraries & Dependencies
- **Chart.js**: Advanced data visualization for player statistics and performance metrics
- **Swiper.js**: Touch-enabled slider implementation for schedules, team rosters, and content carousels
- **JSDataTables**: Enhanced table functionality with sorting, filtering, and pagination
- **Shopify Draggable**: Advanced drag-and-drop functionality for team builder interface
- **Bootstrap Icons**: Comprehensive icon library for improved UI consistency
- **Intersection Observer API**: Native browser API for performance-optimized lazy loading

## Project Organization

The project follows a sophisticated modular architecture designed for scalability and maintainability:

### Core Application Structure
- **/assets/js/global.js**: Main application entry point with App class for centralized initialization and lifecycle management
- **/assets/js/core/**: Core application architecture
  - **module-loader.js**: Dynamic ES6 module loading system with dependency management
  - **page-detector.js**: Intelligent page type detection and required module determination  
  - **feature-observer.js**: Intersection Observer implementation for performance-optimized lazy loading
  - **app-state.js**: Global application state management with persistence support

### Feature Modules
- **/assets/js/modules/**: Feature-specific modular components
  - **utils.js**: Shared utility functions and event management systems
  - **dom-elements.js**: Centralized DOM element caching and management
  - **teambuilder.js**: Drag-and-drop team building interface with state persistence
  - **draft-mode.js**: Advanced draft simulation with challenge filters and round management
  - **live-games.js**: Real-time game tracking with automatic updates
  - **reddit-handlers.js**: Community integration with Reddit API for game threads and feeds
  - **player-handlers.js**: Player statistics, comparisons, and profile management
  - **game-handlers.js**: Game-specific functionality and live data processing

### Styling Architecture
- **/assets/css/global.css**: Main stylesheet with CSS custom properties and layer imports
- **/assets/css/imports/**: Modular CSS architecture using @layer directives
  - **base.css**: Foundation styles and CSS custom properties
  - **animations.css**: Transition and animation definitions
  - **responsive.css**: Mobile-first responsive breakpoints and device adaptations
  - **darkmode-specific.css**: Dark theme implementations and automatic theme switching
  - **team-builder.css**: Team builder and draft mode specific styling
  - **draft-mode.css**: Draft simulation interface styling

### Backend Structure
- **/ajax/**: AJAX endpoints for dynamic content loading and API responses
  - **team-builder.php**: Team roster loading and management endpoints
  - **draft-mode.php**: Draft simulation player fetching and filtering
  - **reddit-feed.php**: Community content aggregation and processing
  - **live-game.php**: Real-time game data processing and updates
- **/includes/**: PHP include files and shared functionality
  - **functions.php**: Core utility functions and shared logic
  - **functions/**: Specialized function libraries organized by feature
  - **data/**: Data structure definitions, constants, and team mappings
  - **tables/**: Reusable table generation functions with caching
- **/pages/**: Static page templates and content structures
- **/templates/**: Reusable UI components and partial views
- **/cache/**: JSON-based caching system for API responses and computed data

### Asset Organization
- **/assets/fonts/**: Custom web fonts and typography resources
- **/assets/img/**: Image assets, team logos, and visual resources organized by category

This architecture enables:
- **Lazy Loading**: Modules are loaded only when needed based on page context
- **Code Splitting**: Features are separated into independent modules for better maintainability
- **Performance Optimization**: Intersection observers and caching reduce unnecessary operations
- **Scalability**: New features can be added as independent modules without affecting existing code

## Project Status

This project is actively maintained and updated throughout the NHL season with the latest data and features.

## Credits

Created by [emils.graphics](https://emils.graphics)