var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
class Observer {
  constructor(element) {
    __publicField(this, "element");
    __publicField(this, "resizeObserver", null);
    __publicField(this, "intersectionObserver", null);
    __publicField(this, "resizeTimeout", null);
    __publicField(this, "throttleTime", 150);
    __publicField(this, "observerOptions", {
      threshold: 0.1,
      rootMargin: "0px"
    });
    this.element = element;
  }
  inView() {
    return new Promise((resolve) => {
      var _a;
      (_a = this.intersectionObserver) == null ? void 0 : _a.disconnect();
      this.intersectionObserver = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry.isIntersecting) {
          resolve(entry);
          this.observeIntersection();
        }
      }, this.observerOptions);
      this.intersectionObserver.observe(this.element);
    });
  }
  resize() {
    return new Promise((resolve) => {
      var _a;
      (_a = this.resizeObserver) == null ? void 0 : _a.disconnect();
      this.resizeObserver = new ResizeObserver((entries) => {
        this.throttle(() => {
          resolve(entries[0]);
          this.observeResize();
        });
      });
      this.resizeObserver.observe(this.element);
    });
  }
  throttle(callback) {
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout);
    }
    this.resizeTimeout = window.setTimeout(() => {
      callback();
      this.resizeTimeout = null;
    }, this.throttleTime);
  }
  observeIntersection() {
    this.intersectionObserver = new IntersectionObserver(
      (entries) => entries[0].isIntersecting && this.inView().catch(console.error),
      this.observerOptions
    );
    this.intersectionObserver.observe(this.element);
  }
  observeResize() {
    this.resizeObserver = new ResizeObserver(() => {
      this.throttle(() => this.resize().catch(console.error));
    });
    this.resizeObserver.observe(this.element);
  }
  disconnect() {
    var _a, _b;
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout);
      this.resizeTimeout = null;
    }
    (_a = this.resizeObserver) == null ? void 0 : _a.disconnect();
    (_b = this.intersectionObserver) == null ? void 0 : _b.disconnect();
  }
}
class Image {
  constructor(element, config) {
    __publicField(this, "element");
    __publicField(this, "config");
    __publicField(this, "picture");
    __publicField(this, "loaded");
    __publicField(this, "observer");
    this.element = element;
    this.config = config;
    this.picture = this.element.closest("picture");
    this.loaded = false;
    this.observer = new Observer(this.element);
    this.init();
  }
  async init() {
    this.observer.inView().then(() => {
      console.log("Image is in view");
    });
    this.observer.resize().then(() => {
      console.log("Image resized", this.config, this.picture, this.loaded);
    });
  }
}
class Picturerino {
  static getConfig(element) {
    return element.getAttribute("data-config");
  }
  static handle(element) {
    const config = Picturerino.getConfig(element);
    new Image(element, config);
  }
}
window.Picturerino = {
  handle: (element) => Picturerino.handle(element)
};
