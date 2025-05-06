export default class CustomProperties {
  constructor(fieldId: string, wrapperId: string, data: string) {
    const el = document.getElementById(wrapperId)

    if (el) {
      el.innerHTML = data + 'lol' +fieldId
    }
  }
}
