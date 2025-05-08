import {ImageResponse} from './types'

export class Loader {
  public requestImage(url: string): Promise<ImageResponse> {
    return fetch(url)
      .then(async response => {
        const data = await response.json()

        if (!response.ok) {
          if(data.error) {
            return Promise.reject(data.error)
          } else {
            return Promise.reject({
              error: {
                message: response.statusText,
                code: response.status,
              },
            })
          }
        }

        return data
      })
  }
}
