export interface DirectoryUpdatedEvent { type: 'directory.updated'; payload: { entryId: string }; }
export type DirectoryEvent = DirectoryUpdatedEvent;

export const directoryEvents = {
  updated: (entryId: string): DirectoryUpdatedEvent => ({ type: 'directory.updated', payload: { entryId } })
};
