export function showPlaylistNotification(url: string | null): void {
  if (url === null || url === '') return
  const ok = window.confirm(`Помодоро начался 🎧\n\nОткрыть плейлист и погнали работать?\n${url}`)
  if (ok) {
    window.open(url, '_blank', 'noopener,noreferrer')
  }
}
