// assets/js/core/module-loader.js
export class ModuleLoader {
    constructor() {
        this.loadedModules = new Map();
        this.pendingModules = new Map();
        this.failedModules = new Set();
    }

    async loadModule(moduleName, condition = true) {
        if (!condition) return null;
        
        // Return cached module if already loaded
        if (this.loadedModules.has(moduleName)) {
            return this.loadedModules.get(moduleName);
        }

        // Don't retry failed modules unless explicitly requested
        if (this.failedModules.has(moduleName)) {
            return null;
        }

        // Return pending promise if module is currently loading
        if (this.pendingModules.has(moduleName)) {
            return this.pendingModules.get(moduleName);
        }

        // Start loading the module
        const modulePromise = this.importModule(moduleName);
        this.pendingModules.set(moduleName, modulePromise);
        
        try {
            const module = await modulePromise;
            this.loadedModules.set(moduleName, module);
            this.pendingModules.delete(moduleName);
            return module;
        } catch (error) {
            this.pendingModules.delete(moduleName);
            this.failedModules.add(moduleName);
            console.error(`Failed to load module: ${moduleName}`, error);
            throw error;
        }
    }

    async importModule(moduleName) {
        // Try to import the module with error handling for different scenarios
        try {
            return await import(`../modules/${moduleName}.js`);
        } catch (error) {
            // If the module doesn't exist, try common variations
            const variations = [
                `../modules/${moduleName}-handler.js`,
                `../modules/${moduleName}s.js`,
                `../modules/${moduleName.replace('-', '_')}.js`
            ];

            for (const variation of variations) {
                try {
                    return await import(variation);
                } catch (variationError) {
                    // Continue to next variation
                }
            }
            
            // If all variations fail, throw the original error
            throw error;
        }
    }

    // Method to retry failed modules
    retryFailedModule(moduleName) {
        this.failedModules.delete(moduleName);
        return this.loadModule(moduleName);
    }

    // Method to check if module is loaded
    isModuleLoaded(moduleName) {
        return this.loadedModules.has(moduleName);
    }

    // Method to get loaded module
    getLoadedModule(moduleName) {
        return this.loadedModules.get(moduleName);
    }

    // Method to clear cache (useful for development)
    clearCache() {
        this.loadedModules.clear();
        this.pendingModules.clear();
        this.failedModules.clear();
    }
}
