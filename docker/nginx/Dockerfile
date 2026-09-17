FROM node:22-alpine AS frontend
WORKDIR /app
COPY barangay-sagip-web/package.json ./
COPY barangay-sagip-web/resources ./resources
COPY barangay-sagip-web/vite.config.js ./vite.config.js
RUN npm install --ignore-scripts
RUN npm run build

FROM nginx:1.29-alpine
COPY barangay-sagip-web/public /var/www/html/public
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null --timeout=5 http://127.0.0.1/up || exit 1

EXPOSE 80
