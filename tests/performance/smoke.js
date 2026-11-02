import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || '';

const latencyTrend = new Trend('request_latency');

export let options = {
  thresholds: {
    request_latency: ['p(95)<500'], // 95% of requests < 500ms
    http_req_duration: ['p(99)<1000'],
  },
  vus: 10,
  duration: '30s',
};

const endpoints = [
  { method: 'GET', path: '/platform/health/iam' },
  { method: 'GET', path: '/api/auth/me', headers: { Authorization: `Bearer ${AUTH_TOKEN}` } },
  { method: 'GET', path: '/api/wallet/me', headers: { Authorization: `Bearer ${AUTH_TOKEN}` } },
];

export default function () {
  for (const ep of endpoints) {
    const url = `${BASE_URL}${ep.path}`;
    const params = {
      headers: ep.headers || {},
    };
    const res = http.request(ep.method, url, null, params);
    latencyTrend.add(res.timings.duration);
    check(res, {
      [ep.path + ' status 200']: (r) => r.status === 200,
    });
    sleep(1);
  }
}
