export interface ImageResponse {
    attributes: {
      src: string,
      width: number,
      height: number
    }
}

export interface ImageConfig {
    element: HTMLImageElement;
    config: string;
}
