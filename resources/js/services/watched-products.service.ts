import axios from "axios";

export const sendWatchInfo = async (user_id: number, product_code: number) => {
  await axios.post(`/users/${user_id}/watched`, {product_code});
};

export const getWatchedProducts = async (user_id: number) => {
  const response = await axios.get(`/users/${user_id}/watched`);
  return response.data?.products;
}

export const removeWatchedProduct = async (user_id: number, product_code: number) => {
  await axios.delete(`/users/${user_id}/watched`, {
    data: {product_code}
  });
}
