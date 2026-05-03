import {onMounted, ref, watch} from "vue";

export default function (initialValue, key) {
  const val = ref(initialValue);

  onMounted(() => {
    const storageVal = window.localStorage.getItem(key);

    if (storageVal) {
      val.value = JSON.parse(storageVal);
    }

    watch(
      val,
      val => {
        window.localStorage.setItem(key, JSON.stringify(val));
      },
      {deep: true}
    );
  });

  return val;
}