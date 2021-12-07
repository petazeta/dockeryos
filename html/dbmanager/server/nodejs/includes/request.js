import requestsAuth from './requests.js';

async function makeRequest(user, action, parameters) {
  const reqAuth=requestsAuth.get(action);
  if (!reqAuth) throw new Error("no action recognised: " + action);
  return await reqAuth(parameters, user);
}
  
export default makeRequest;
