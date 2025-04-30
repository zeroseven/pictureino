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
    img1x?: string,
    img2x?: string,
    width: number,
    height: number,
  }
  view: number;
}

export interface SourceMap {[key: number]: HTMLSourceElement}
