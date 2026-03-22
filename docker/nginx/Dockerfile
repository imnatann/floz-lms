FROM node:20-alpine AS assets

WORKDIR /app

COPY src/package*.json ./
RUN npm ci

COPY src ./
RUN npm run build

FROM nginx:1.25-alpine

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=assets /app/public /var/www/html/public
