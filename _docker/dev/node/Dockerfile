FROM node:22-alpine

WORKDIR /app

COPY front/package*.json /app/
COPY front/package-lock.json* /app/

RUN npm install && npm cache clean --force

RUN cp -r node_modules node_modules_backup

COPY front /app

RUN cp -r node_modules_backup/* node_modules/ 2>/dev/null || true && rm -rf node_modules_backup

EXPOSE 5173

CMD ["npm", "run", "dev"]