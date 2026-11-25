<footer class="footer glass-footer">
  <div class="flex flex-col items-center justify-center gap-3 px-4 py-5 lg:flex-row lg:justify-between xxl:px-8">
    <p class="text-sm max-md:w-full max-md:text-center lg:text-base">
      Copyright &copy; <span id="current-year">{{ date('Y') }}</span>
      <a class="text-primary font-medium" href="{{ route('home') }}">Q4 International</a>.
      All rights reserved.
    </p>
    <div class="justify-center max-md:flex max-md:w-full"></div>
    @include('partials._social')
    <ul class="flex gap-3 text-sm max-lg:w-full max-lg:justify-center lg:gap-4 lg:text-base">
      <li>
        <a href="#">Privacy Policy</a>
      </li>
      <li>
        <a href="#">Terms of condition</a>
      </li>
    </ul>
  </div>
</footer>