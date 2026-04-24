FROM node:22-alpine

WORKDIR /app

COPY front/package*.json /app/
COPY front/package-lock.json* /app/

RUN npm install

COPY front /app

EXPOSE 5173

CMD ["npm", "run", "dev"]