export interface ElementSize {
  width: number;
  height: number;
}

export interface ImageResponse {
  error?: {
    message: string,
    code: number,
  };
  processed: {
    img: string,
    img2x?: string,
    width: number,
    height: number,
  }
  view: number;
  aspectRatio: [number, number];
}

export interface SourceMap {[key: number]: HTMLSourceElement}
