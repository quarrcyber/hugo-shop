FROM node:24-alpine AS assets
WORKDIR /workspace
COPY tokens.css ./tokens.css
WORKDIR /workspace/services/shop
COPY services/shop/package.json services/shop/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY services/shop ./
RUN npm run build

FROM nginx:1.28-alpine
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY services/shop/public /var/www/html/public
COPY --from=assets /workspace/services/shop/public/build /var/www/html/public/build
