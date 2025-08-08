// assets/js/core/feature-observer.js
export class FeatureObserver {
    constructor(moduleLoader) {
        this.moduleLoader = moduleLoader;
        this.observedElements = new Map();
        this.observer = new IntersectionObserver(
            this.handleIntersection.bind(this), 
            {
                root: null,
                rootMargin: '50px',
                threshold: 0.1
            }
        );
    }

    observeElement(element, moduleName, options = {}) {
        if (!element || !moduleName) {
            return;
        }

        // Store element data
        element.dataset.module = moduleName;
        if (options.once !== false) {
            element.dataset.observeOnce = 'true';
        }
        
        this.observedElements.set(element, {
            moduleName,
            options,
            loaded: false
        });

        this.observer.observe(element);
    }

    async handleIntersection(entries) {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                const element = entry.target;
                const elementData = this.observedElements.get(element);
                
                if (!elementData || elementData.loaded) {
                    continue;
                }

                const moduleName = elementData.moduleName;
                
                try {
                    const module = await this.moduleLoader.loadModule(moduleName);
                    
                    if (module) {
                        // Mark as loaded
                        elementData.loaded = true;
                        
                        // Execute callback if provided
                        if (elementData.options.onLoad) {
                            elementData.options.onLoad(module, element);
                        }

                        // Unobserve if set to observe once
                        if (element.dataset.observeOnce === 'true') {
                            this.observer.unobserve(element);
                            this.observedElements.delete(element);
                        }

                        // Dispatch custom event
                        element.dispatchEvent(new CustomEvent('moduleLoaded', {
                            detail: { moduleName, module }
                        }));
                    }
                } catch (error) {
                    console.error(`Failed to load module ${moduleName} on intersection:`, error);
                }
            }
        }
    }

    // Method to manually trigger loading for an element
    async loadElementModule(element) {
        const elementData = this.observedElements.get(element);
        if (!elementData) {
            return null;
        }

        if (elementData.loaded) {
            return this.moduleLoader.getLoadedModule(elementData.moduleName);
        }

        try {
            const module = await this.moduleLoader.loadModule(elementData.moduleName);
            elementData.loaded = true;
            return module;
        } catch (error) {
            console.error('Failed to manually load element module:', error);
            throw error;
        }
    }

    // Method to stop observing an element
    unobserveElement(element) {
        this.observer.unobserve(element);
        this.observedElements.delete(element);
    }

    // Method to stop observing all elements
    disconnect() {
        this.observer.disconnect();
        this.observedElements.clear();
    }
}
