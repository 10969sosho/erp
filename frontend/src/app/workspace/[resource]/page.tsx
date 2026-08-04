import { notFound } from 'next/navigation';
import { ResourceWorkspace } from '@/components/resource-workspace';
import { resourceMap, resources } from '@/lib/resources';

export function generateStaticParams() {
  return resources.map((resource) => ({ resource: resource.slug }));
}

export default async function ResourcePage({ params }: { params: Promise<{ resource: string }> }) {
  const { resource: slug } = await params;
  const resource = resourceMap.get(slug);
  if (!resource) notFound();
  return <ResourceWorkspace resource={resource} />;
}
