import { ImageResponse } from './types';

export class ApiService {
    static getOptimizedImage(config: string, width: number, height: number): Promise<ImageResponse> {
        const viewWidth = Math.round(window.innerWidth);
        return fetch(`/-/img/${width}x${height}/${viewWidth}/${config}/`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    }
}
