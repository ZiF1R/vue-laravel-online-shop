<template>
  <h1>История заказов</h1>
  <template v-if="user.isAuthorized">
    <div class="orders">
      <div class="order" v-for="(order, i) in orderIds">
        <h2>Заказ #{{order}}</h2>
        <div class="order__products">
          <div class="products__product" v-for="product in ordersVal[i]">
            <div class="product__info">
              <router-link :to="getProductLink(product)">
                {{product.name}}
              </router-link>
              Количество: {{product.count}}
              Цена: {{product.price}}
            </div>
          </div>
          <div class="summary">
            Итого: {{ordersTotalPrices[i]}}
          </div>
        </div>
      </div>
    </div>
  </template>
  <p v-else>Войдите для доступа к данной странице</p>
</template>

<script setup lang="ts">
import {useUserStore} from "../../stores/user";
import {computed, onMounted, ref} from "vue";
import {getUserOrders} from "../../services/orders.service";

const user = useUserStore();
const orders = ref([]);
const groupedOrders = computed(() => {
  return orders.value.reduce((acc, o) => {
    const orderId = o.id;
    if (orderId in acc) {
      acc[orderId].push(o);
    } else {
      acc[orderId] = [o];
    }
    return acc;
  }, {});
});
const ordersTotalPrices = computed(() => {
  const arr = [];
  for (const order in groupedOrders.value) {
    let total = 0;
    for (const product of groupedOrders.value[order]) {
      total += +product.price * product.count;
    }
    arr.push(total.toFixed(2));
  }

  return arr;
});
const orderIds = computed(() => Object.keys(groupedOrders.value));
const ordersVal = computed(() => Object.values(groupedOrders.value));

onMounted(async () => {
  if (user.isAuthorized) {
    orders.value = await getUserOrders(+user.getUser().id);
  }
});

function getProductLink(product) {
  return `/categories/${product.category_id}/products/${product.product_code}`;
}
</script>

<style scoped>
h1 {
  margin-bottom: 20px;
}

.order {
  margin-bottom: 15px;
}

h2 {
  margin-bottom: 5px;
}
</style>
