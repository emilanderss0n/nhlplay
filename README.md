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

## Project Organization & Documentation

The project follows a sophisticated modular architecture designed for scalability and maintainability.

### Architecture Overview

#### Core System Components
- **Custom PHP Router** - No external framework dependencies with SEO-friendly URL rewriting
- **Modular JavaScript Architecture** - ES6 modules with dynamic loading and state management
- **NHL API Integration** - Comprehensive caching system with multiple API endpoint support
- **File-based Caching** - JSON caching with intelligent refresh and fallback strategies
- **AJAX Navigation** - SPA-like experience without full page reloads

#### Directory Structure
```
/assets/js/          # JavaScript modules and core application files
  /core/             # Core system (module loader, state management, page detection)
  /modules/          # Feature-specific modules (teams, players, games, etc.)
/ajax/               # AJAX endpoints for dynamic content
/pages/              # Main page templates
/includes/           # Core PHP system files
  /controllers/      # Business logic controllers
  /functions/        # Categorized utility functions
  /data/             # Data processing and initialization
/cache/              # JSON cache files for API responses
```

#### Key Features
- **Dynamic Module Loading** - JavaScript modules loaded based on page requirements
- **Intelligent Caching** - Multi-level caching with TTL and fallback mechanisms
- **SEO Optimization** - Dynamic meta tags, canonical URLs, and clean URL structure
- **Error Resilience** - Graceful degradation and comprehensive error handling
- **Performance Focused** - Intersection observers, lazy loading, and optimized API usage

## Getting Started

### For Users
Simply navigate to the application and start exploring NHL statistics, live games, and team information. The interface is intuitive and responsive across all devices.

### For Developers
1. **Understand the Architecture** - Review the modular PHP and JavaScript architecture above
2. **Explore the Codebase** - Begin with `index.php`, then `includes/router.php`, and `assets/js/global.js`
3. **Check Function Libraries** - Review `includes/functions/` for available utilities
4. **API Integration** - Examine `includes/functions/nhl-api.php` for NHL API patterns
5. **Module System** - Study `assets/js/core/module-loader.js` for dynamic loading patterns

### Development Environment
- **Local Setup** - Designed to work with WAMP/XAMPP in `/nhl/` subdirectory
- **Production Ready** - Automatically handles root domain deployment
- **No Build Process** - Uses native ES6 modules, no compilation required
- **File-based Caching** - Automatically creates and manages cache directory

## Project Status

This project is actively maintained and updated throughout the NHL season (2024-2025) with:
- Latest NHL API integrations
- Current season data and statistics
- Real-time game tracking and updates
- Performance optimizations and caching strategies
- Mobile-first responsive design
- SEO optimization and clean URL structure

### Recent Updates
- **September 2025** - Enhanced architecture and documentation
- **Season 2024-2025** - Updated for current NHL season
- **Performance Optimizations** - Enhanced caching and API integration

## Credits

Created by [emils.graphics](https://emils.graphics)