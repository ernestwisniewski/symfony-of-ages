/// <reference types="vite/client" />
/// <reference types="vite-plugin-symfony/stimulus/env" />

// Global type declarations for the project
declare module '*.jpg' {
  const src: string;
  export default src;
}

declare module '*.jpeg' {
  const src: string;
  export default src;
}

declare module '*.png' {
  const src: string;
  export default src;
}

declare module '*.svg' {
  const src: string;
  export default src;
}

declare module '*.gif' {
  const src: string;
  export default src;
}

declare module '*.webp' {
  const src: string;
  export default src;
}

// PixiJS global type improvements
declare global {
  interface Window {
    PIXI: typeof import('pixi.js');
  }
} 