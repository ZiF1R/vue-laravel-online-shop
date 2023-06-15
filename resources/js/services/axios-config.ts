import axios from "axios";

axios.defaults.headers.common = {
  "Content-Type": "application/json;charset=utf-8",
};
axios.defaults.baseURL =
  "http://127.0.0.1:8000/api";
