<template>
  <h1>Личные данные</h1>
  <form class="fields" @submit.prevent="saveChanges">
    <CustomInput type="text" v-model.trim="currentUser.name" label="Имя" />
    <CustomInput type="email" v-model.trim="currentUser.newMail" label="Почта" />
    <CustomInput type="tel" v-model.trim="currentUser.phone" label="Телефон" pattern="375\d{9}" />
    <CustomInput type="date" v-model.trim="currentUser.birth" label="Дата рождения" />
    <span class="error">{{errorMessage}}</span>

    <button type="submit" class="button_primary">Сохранить</button>
  </form>
</template>

<script setup lang="ts">
import {useUserStore} from "../../stores/user";
import {computed, ref} from "vue";
import {changeUserData} from "../../services/users.service";
import CustomInput from "../../components/CustomElements/CustomInput.vue";
import {useRouter} from "vue-router";

const router = useRouter();
const user = useUserStore();
const currentUser = computed(() => ({...user.getUser(), newMail: user.getUser().mail}));
const errorMessage = ref("");

async function saveChanges() {
  if (currentUser.value.birth && new Date(currentUser.value.birth) > Date.now()) {
    errorMessage.value = "Введите корректную дату";
    return;
  }
  if (currentUser.value.name && currentUser.value.mail) {
    const newUserData = await changeUserData(currentUser.value);
    if (newUserData.user) {
      user.setUser(newUserData.user);
      errorMessage.value = "";
    } else {
      errorMessage.value = newUserData?.error;
    }
  } else {
    errorMessage.value = "Поля с именем и почтой не должны быть пустыми";
  }
}
</script>

<style scoped>
.fields {
  display: flex;
  flex-direction: column;
  width: 400px;
}

.button_primary {
  margin-top: 15px;
  width: fit-content;
}

.error {
  color: coral;
}
</style>
