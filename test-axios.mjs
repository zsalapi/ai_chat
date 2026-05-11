import axios from 'axios';

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api'
});

console.log("Axios request to /products:", api.getUri({url: '/products'}));
console.log("Axios request to products:", api.getUri({url: 'products'}));
console.log("Axios request to /api/products:", api.getUri({url: '/api/products'}));
console.log("Axios request to /api/products (with trailing slash baseURL):");

const api2 = axios.create({
    baseURL: 'http://127.0.0.1:8000/api/'
});

console.log("With trailing slash, to /products:", api2.getUri({url: '/products'}));
console.log("With trailing slash, to products:", api2.getUri({url: 'products'}));
