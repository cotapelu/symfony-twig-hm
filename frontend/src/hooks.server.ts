import { redirect } from "@sveltejs/kit";
import type { Handle, HandleServerError } from "@sveltejs/kit";
import { API_BASE } from "$lib/config";

interface Locals {
  user?: any;
  token?: string;
}

export const handle: Handle = async ({ event, resolve }) => {
  const isApiRoute = event.url.pathname.startsWith("/api");

  if (isApiRoute) {
    return handleApiRoute(event);
  }

  await handleAuthentication(event);

  const response = await resolve(event);
  addSecurityHeaders(response);

  return response;
};

export async function handleApiRoute(event: any) {
  const backendUrl = `${API_BASE}${event.url.pathname}${event.url.search}`;

  try {
    const response = await fetch(backendUrl, {
      method: event.request.method,
      headers: {
        ...Object.fromEntries(event.request.headers),
        host: new URL(API_BASE).host,
      },
      body:
        event.request.method !== "GET" && event.request.method !== "HEAD"
          ? await event.request.text()
          : undefined,
    });

    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: response.headers,
    });
  } catch (error) {
    console.error("API proxy error:", error);
    return new Response(
      JSON.stringify({ error: "Backend service unavailable" }),
      {
        status: 503,
        headers: { "Content-Type": "application/json" },
      },
    );
  }
}

export async function handleAuthentication(event: any) {
  const pathname = event.url.pathname;

  const protectedRoutes = [
    "/messages",
    "/groups",
    "/calls",
    "/files",
    "/profile",
    "/settings",
    "/dashboard",
  ];

  const requiresAuth = protectedRoutes.some((route) =>
    pathname.startsWith(route),
  );

  if (requiresAuth) {
    const token =
      event.cookies.get("auth_token") ||
      event.request.headers.get("authorization")?.replace("Bearer ", "");

    if (!token) {
      throw redirect(302, "/auth");
    }

    try {
      const response = await fetch(`${API_BASE}/api/user/profile`, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        event.cookies.delete("auth_token", { path: "/" });
        throw redirect(302, "/auth");
      }

      const userData = await response.json();
      event.locals.user = userData.user;
      event.locals.token = token;
    } catch (error) {
      console.error("Token validation error:", error);
      event.cookies.delete("auth_token", { path: "/" });
      throw redirect(302, "/auth");
    }
  }

  if (pathname.startsWith("/auth") && event.locals.user) {
    throw redirect(302, "/dashboard");
  }
}

export function addSecurityHeaders(response: Response) {
  response.headers.set("X-Frame-Options", "DENY");
  response.headers.set("X-Content-Type-Options", "nosniff");
  response.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  response.headers.set(
    "Permissions-Policy",
    "camera=(), microphone=(), geolocation=()",
  );

  const mercureUrl =
    import.meta.env.VITE_MERCURE_URL || "wss://mercure.example.com";
  response.headers.set(
    "Content-Security-Policy",
    "default-src 'self'; " +
      "script-src 'self' https://cdn.jsdelivr.net; " +
      "style-src 'self' https://fonts.googleapis.com; " +
      "font-src 'self' https://fonts.gstatic.com; " +
      "img-src 'self' data: https:; " +
      "connect-src 'self' https://api.openai.com " +
      mercureUrl +
      "; " +
      "frame-src 'self' https://www.youtube.com;",
  );

  const contentType = response.headers.get("content-type");
  if (
    contentType?.includes("text/css") ||
    contentType?.includes("application/javascript")
  ) {
    response.headers.set(
      "Cache-Control",
      "public, max-age=31536000, immutable",
    );
  }
}

export function handleError({ error, event }: { error: any; event: any }): {
  message: string;
  code: number;
} {
  console.error("Server error:", error);

  return {
    message: "Internal server error",
    code: 500,
  };
}

export async function handleFetch({
  request,
  fetch: fetchImpl,
}: {
  request: Request;
  fetch: typeof fetch;
}) {
  if (request.url.startsWith("/api/")) {
    request = new Request(`${API_BASE}${request.url}`, request);
  }

  return fetchImpl(request);
}
