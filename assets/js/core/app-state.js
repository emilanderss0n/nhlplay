// assets/js/core/app-state.js
export class AppState {
    constructor() {
        this.state = {
            currentPage: null,
            loadedModules: new Set(),
            user: {
                preferences: this.loadUserPreferences()
            },
            ui: {
                darkMode: false,
                sidebarCollapsed: false
            },
            data: {
                teams: null,
                currentSeason: null,
                lastUpdate: null
            }
        };
        
        this.listeners = new Map();
        this.loadStateFromStorage();
    }

    // State management methods
    setState(path, value) {
        const oldValue = this.getState(path);
        this.setNestedProperty(this.state, path, value);
        
        // Notify listeners
        this.notifyListeners(path, value, oldValue);
        
        // Persist certain state changes
        this.persistState();
    }

    getState(path) {
        if (!path) return this.state;
        return this.getNestedProperty(this.state, path);
    }

    // Subscribe to state changes
    subscribe(path, callback) {
        if (!this.listeners.has(path)) {
            this.listeners.set(path, new Set());
        }
        this.listeners.get(path).add(callback);

        // Return unsubscribe function
        return () => {
            const pathListeners = this.listeners.get(path);
            if (pathListeners) {
                pathListeners.delete(callback);
                if (pathListeners.size === 0) {
                    this.listeners.delete(path);
                }
            }
        };
    }

    // Notify listeners of state changes
    notifyListeners(path, newValue, oldValue) {
        // Notify exact path listeners
        const pathListeners = this.listeners.get(path);
        if (pathListeners) {
            pathListeners.forEach(callback => {
                try {
                    callback(newValue, oldValue, path);
                } catch (error) {
                    console.error('Error in state listener:', error);
                }
            });
        }

        // Notify wildcard listeners (path.*)
        this.listeners.forEach((listeners, listenerPath) => {
            if (listenerPath.endsWith('*') && path.startsWith(listenerPath.slice(0, -1))) {
                listeners.forEach(callback => {
                    try {
                        callback(newValue, oldValue, path);
                    } catch (error) {
                        console.error('Error in wildcard state listener:', error);
                    }
                });
            }
        });

        // Dispatch global state change event
        document.dispatchEvent(new CustomEvent('appStateChange', {
            detail: { path, newValue, oldValue }
        }));
    }

    // Utility methods
    setNestedProperty(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            if (!(key in current) || typeof current[key] !== 'object') {
                current[key] = {};
            }
            current = current[key];
        }
        
        current[keys[keys.length - 1]] = value;
    }

    getNestedProperty(obj, path) {
        return path.split('.').reduce((current, key) => {
            return current && current[key] !== undefined ? current[key] : undefined;
        }, obj);
    }

    // Persistence methods
    loadStateFromStorage() {
        try {
            const savedState = localStorage.getItem('nhl-app-state');
            if (savedState) {
                const parsed = JSON.parse(savedState);
                // Only load specific parts of state from storage
                if (parsed.ui) {
                    Object.assign(this.state.ui, parsed.ui);
                }
                if (parsed.user) {
                    Object.assign(this.state.user, parsed.user);
                }
            }
        } catch (error) {
            console.warn('Failed to load state from storage:', error);
        }
    }

    persistState() {
        try {
            const stateToPersist = {
                ui: this.state.ui,
                user: this.state.user
            };
            localStorage.setItem('nhl-app-state', JSON.stringify(stateToPersist));
        } catch (error) {
            console.warn('Failed to persist state:', error);
        }
    }

    loadUserPreferences() {
        try {
            const prefs = localStorage.getItem('nhl-user-preferences');
            return prefs ? JSON.parse(prefs) : {
                favoriteTeam: null,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                theme: 'auto'
            };
        } catch (error) {
            return {
                favoriteTeam: null,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                theme: 'auto'
            };
        }
    }

    // Module tracking
    addLoadedModule(moduleName) {
        this.state.loadedModules.add(moduleName);
        this.notifyListeners('loadedModules', Array.from(this.state.loadedModules));
    }

    isModuleLoaded(moduleName) {
        return this.state.loadedModules.has(moduleName);
    }

    // Reset state
    reset() {
        this.state = {
            currentPage: null,
            loadedModules: new Set(),
            user: {
                preferences: this.loadUserPreferences()
            },
            ui: {
                darkMode: false,
                sidebarCollapsed: false
            },
            data: {
                teams: null,
                currentSeason: null,
                lastUpdate: null
            }
        };
        this.persistState();
        this.notifyListeners('*', this.state);
    }
}

// Create global instance
export const appState = new AppState();
