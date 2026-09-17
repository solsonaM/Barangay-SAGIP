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
EXPOSE 80
