import { ImageResponse } from './types';

export class ApiService {
    private static async fetchImageUrl(config: string, width: number, height: number): Promise<ImageResponse> {
        const response = await fetch(`/-/img/${width}x${height}/${config}/`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    }

    static async getOptimizedImage(config: string, width: number, height: number): Promise<ImageResponse> {
        try {
            return await this.fetchImageUrl(config, width, height);
        } catch (error) {
            console.error('Failed to fetch optimized image:', error);
            throw error;
        }
    }
}
