import { ImageResponse } from './types';

export class Loader {
  private cache: Map<string, ImageResponse>;

  constructor() {
    this.cache = new Map();
  }

  public requestImage(url: string): Promise<ImageResponse> {
    if (this.cache.has(url)) {
      const cachedImage = this.cache.get(url);
      if (cachedImage) {
        return Promise.resolve(cachedImage);
      }
    }

    return fetch(url)
      .then(async response => {
        const data = await response.json();

        if (!response.ok) {
          if(data.error) {
            return Promise.reject(data.error);
          } else {
            return Promise.reject({
              error: {
                message: response.statusText,
                code: response.status,
              }
            });
          }
        }

        return data;
      })
      .then((config: ImageResponse) => {
        this.cache.set(url, config);
        return config;
      })
  }

  public clearCache(): void {
    this.cache.clear();
  }

  public removeFromCache(url: string): void {
    this.cache.delete(url);
  }
}
