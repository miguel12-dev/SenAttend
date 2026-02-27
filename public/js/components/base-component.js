/**
 * Component Base Class
 * Clase base para componentes reutilizables
 * Implementa ciclo de vida y gestión de estado
 * 
 * @module Component
 * @version 1.0.0
 */

class Component {
  constructor(selector, props = {}) {
    this.selector = selector;
    this.props = props;
    this.state = {};
    this.element = null;
    this.mounted = false;
    this.subscriptions = [];
    this.eventListeners = [];
  }

  /**
   * Inicializa el componente
   */
  async init() {
    this.element = document.querySelector(this.selector);
    
    if (!this.element) {
      console.warn(`[Component] Elemento no encontrado: ${this.selector}`);
      return;
    }

    await this.beforeMount();
    await this.mount();
    this.mounted = true;
    await this.afterMount();
  }

  /**
   * Hooks del ciclo de vida
   */
  async beforeMount() {
    // Override en subclases
  }

  async mount() {
    const html = await this.render();
    this.element.innerHTML = html;
    this.attachEventListeners();
  }

  async afterMount() {
    // Override en subclases
  }

  async beforeUpdate() {
    // Override en subclases
  }

  async update() {
    if (!this.mounted) return;
    
    await this.beforeUpdate();
    this.removeEventListeners();
    await this.mount();
    await this.afterUpdate();
  }

  async afterUpdate() {
    // Override en subclases
  }

  async beforeUnmount() {
    // Override en subclases
  }

  async unmount() {
    await this.beforeUnmount();
    this.removeEventListeners();
    this.unsubscribeAll();
    
    if (this.element) {
      this.element.innerHTML = '';
    }
    
    this.mounted = false;
  }

  /**
   * Renderiza el componente
   */
  async render() {
    return ''; // Override en subclases
  }

  /**
   * Gestión de estado local
   */
  setState(updates) {
    const hasChanged = Object.entries(updates).some(([key, value]) => {
      return this.state[key] !== value;
    });

    if (hasChanged) {
      this.state = { ...this.state, ...updates };
      this.update();
    }
  }

  /**
   * Gestión de eventos
   */
  addEventListener(selector, event, handler, options = {}) {
    const element = selector instanceof Element 
      ? selector 
      : this.element.querySelector(selector);

    if (element) {
      element.addEventListener(event, handler, options);
      this.eventListeners.push({ element, event, handler, options });
    }
  }

  attachEventListeners() {
    // Override en subclases
  }

  removeEventListeners() {
    this.eventListeners.forEach(({ element, event, handler, options }) => {
      element.removeEventListener(event, handler, options);
    });
    this.eventListeners = [];
  }

  /**
   * Suscripción a estado global
   */
  subscribe(path, callback) {
    if (window.stateManager) {
      const unsubscribe = window.stateManager.subscribe(path, callback);
      this.subscriptions.push(unsubscribe);
    }
  }

  unsubscribeAll() {
    this.subscriptions.forEach(unsubscribe => unsubscribe());
    this.subscriptions = [];
  }

  /**
   * Helpers de consulta
   */
  $(selector) {
    return this.element.querySelector(selector);
  }

  $$(selector) {
    return Array.from(this.element.querySelectorAll(selector));
  }

  /**
   * Emit custom event
   */
  emit(eventName, detail = {}) {
    const event = new CustomEvent(eventName, {
      detail,
      bubbles: true,
      cancelable: true
    });
    this.element.dispatchEvent(event);
  }

  /**
   * Listen to custom event
   */
  on(eventName, handler) {
    this.element.addEventListener(eventName, handler);
  }
}

/**
 * Factory para crear componentes
 */
class ComponentFactory {
  static components = new Map();

  static register(name, ComponentClass) {
    this.components.set(name, ComponentClass);
  }

  static create(name, selector, props) {
    const ComponentClass = this.components.get(name);
    
    if (!ComponentClass) {
      throw new Error(`Component ${name} not registered`);
    }

    return new ComponentClass(selector, props);
  }

  static async mount(name, selector, props) {
    const component = this.create(name, selector, props);
    await component.init();
    return component;
  }
}

/**
 * Decorador para registrar componentes automáticamente
 */
function RegisterComponent(name) {
  return function(ComponentClass) {
    ComponentFactory.register(name, ComponentClass);
    return ComponentClass;
  };
}

export { Component as default, ComponentFactory, RegisterComponent };
