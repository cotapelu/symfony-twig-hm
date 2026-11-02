import { apiClient } from '../../../appcore/api-client';

export class GroupsService {
  private baseUrl = '/api/groups';

  async getGroups() {
    return apiClient.get(`${this.baseUrl}`);
  }

  async getGroup(id: string) {
    return apiClient.get(`${this.baseUrl}/${id}`);
  }

  async createGroup(groupData: { name: string; description: string; isPrivate: boolean }) {
    return apiClient.post(`${this.baseUrl}`, groupData);
  }

  async joinGroup(groupId: string) {
    return apiClient.post(`${this.baseUrl}/${groupId}/join`);
  }

  async leaveGroup(groupId: string) {
    return apiClient.post(`${this.baseUrl}/${groupId}/leave`);
  }
}