import axios from 'axios';

async function test() {
  try {
    const login = await axios.post('http://127.0.0.1:8000/api/login', {
        email: 'test@example.com',
        password: 'password'
    });
    
    const token = login.data.data.token;
    
    // Test POST Create with strings
    const newProduct = {
        name: 'Test Product String ' + Math.random().toString(36).substring(7),
        description: 'Test description format',
        price: "99.99",
        quantity: "15"
    };
    
    console.log("Sending POST to /api/products...");
    const response = await axios.post('http://127.0.0.1:8000/api/products', newProduct, {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    });

    console.log("Update successful! Response status:", response.status);
    console.log("Returned Data:", response.data);

  } catch (e) {
      console.error("Error occurred!");
      if (e.response && e.response.data) {
          console.error(e.response.status, JSON.stringify(e.response.data, null, 2));
      } else {
          console.error(e);
      }
  }
}

test();
