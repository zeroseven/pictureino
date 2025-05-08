declare module '@typo3/backend/icons.js' {
  export default class Icons {
      static getIcon(name: string, size: string): Promise<HTMLElement>;
      static sizes: {
          small: string;
          medium: string;
          large: string;
      };
  }
}
