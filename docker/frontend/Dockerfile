FROM node:22-alpine AS deps

WORKDIR /app
COPY frontend/package.json ./
RUN npm install --no-audit --no-fund

FROM node:22-alpine AS builder

WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY frontend/ .
ENV NEXT_TELEMETRY_DISABLED=1
ARG NEXT_PUBLIC_API_URL=/api/v1
ARG NEXT_PUBLIC_BASE_PATH=/app
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_BASE_PATH=$NEXT_PUBLIC_BASE_PATH
RUN npm run build

FROM node:22-alpine AS runner

WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ARG NEXT_PUBLIC_BASE_PATH=/app
ENV NEXT_PUBLIC_BASE_PATH=$NEXT_PUBLIC_BASE_PATH
# The standalone server only needs `node`; the bundled npm/corepack ship their own
# vulnerable dependencies (e.g. tar) that Trivy flags as CRITICAL.
RUN apk upgrade --no-cache \
    && apk add --no-cache wget \
    && rm -rf /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/corepack \
        /usr/local/bin/npm /usr/local/bin/npx /usr/local/bin/corepack \
    && addgroup -g 1001 rms \
    && adduser -u 1001 -G rms -s /bin/sh -D rms

COPY --from=builder /app/public ./public
COPY --from=builder --chown=rms:rms /app/.next/standalone ./
COPY --from=builder --chown=rms:rms /app/.next/static ./.next/static

USER rms
EXPOSE 3000
ENV PORT=3000
ENV HOSTNAME=0.0.0.0

HEALTHCHECK --interval=15s --timeout=5s --retries=3 \
    CMD wget -qO- http://127.0.0.1:3000/app/health || exit 1

CMD ["node", "server.js"]
