import { apiClient } from '@/shared/api/client'

export interface PlaylistResponse {
  data: {
    url: string | null
  }
}

export interface SavePlaylistPayload {
  url: string
}

export function getPlaylist(): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist')
}

export function savePlaylist(payload: SavePlaylistPayload): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist', {
    method: 'POST',
    body: payload,
  })
}

export function deletePlaylist(): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist', {
    method: 'DELETE',
  })
}
