FROM node:16.13.0
# Create app directory
WORKDIR /usr/src/app
# Install app dependencies
COPY ./html/dbmanager/package*.json ./
RUN npm install
# Copy app source code
COPY ./html/dbmanager ./
#Expose port and start application
EXPOSE 8020
CMD [ "npm", "start" ]