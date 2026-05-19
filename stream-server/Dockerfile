FROM node:20-alpine
WORKDIR /app
COPY package.json .
RUN npm install --omit=dev
# Remove utp-native prebuilds to prevent SIGSEGV on Linux/WSL2; WebTorrent falls back to TCP
RUN find /app/node_modules/utp-native -name "*.node" -delete 2>/dev/null || true
COPY server.js .
RUN mkdir -p /tmp/torrents
EXPOSE 9090
CMD ["node", "server.js"]
