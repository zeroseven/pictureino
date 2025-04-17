export interface ImageResponse {
    attributes: {
      src: string,
      width: number,
      height: number
    },
    aspectRatio: [number, number]
}
